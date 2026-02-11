<?php

class AdminController extends Controller
{
    public function filters()
    {
        return ['accessControl'];
    }

    public function accessRules()
    {
        return [
            ['allow', 'actions' => ['login'], 'users' => ['*']],
            ['allow', 'actions' => ['index', 'logout', 'savePrizes', 'uploadParticipants', 'resetWinners', 'settings', 'exportWinners'], 'users' => ['@']],
            ['deny', 'users' => ['*']],
        ];
    }

    public function actionLogin()
    {
        $error = '';
        if (Yii::app()->request->isPostRequest) {
            $username = (string) Yii::app()->request->getPost('username', '');
            $password = (string) Yii::app()->request->getPost('password', '');

            $identity = new UserIdentity($username, $password);
            if ($identity->authenticate()) {
                Yii::app()->user->login($identity);
                $this->redirect(['admin/index']);
                return;
            }
            $error = 'Sai tài khoản hoặc mật khẩu';
        }
        $this->render('login', ['error' => $error]);
    }

    public function actionLogout()
    {
        Yii::app()->user->logout();
        $this->redirect(['admin/login']);
    }

    public function actionIndex()
    {
        $prizes = Prize::model()->findAll(['order' => 'prize_order ASC']);
        $participantsCount = (int) Yii::app()->db->createCommand("SELECT COUNT(*) FROM participants")->queryScalar();
        $activeCount = (int) Yii::app()->db->createCommand("SELECT COUNT(*) FROM participants WHERE is_active=1")->queryScalar();

        $lastWinners = Yii::app()->db->createCommand("
      SELECT w.won_at, pr.prize_name, p.code, p.full_name, p.department, p.company
      FROM winners w
      JOIN prizes pr ON pr.id=w.prize_id
      JOIN participants p ON p.id=w.participant_id
      ORDER BY w.won_at DESC
      LIMIT 20
    ")->queryAll();

        $settingsRows = Yii::app()->db->createCommand("SELECT name, value FROM settings")->queryAll();
        $settings = [];
        foreach ($settingsRows as $r) {
            $settings[$r['name']] = $r['value'];
        }

        $this->render('index', [
            'prizes' => $prizes,
            'participantsCount' => $participantsCount,
            'activeCount' => $activeCount,
            'lastWinners' => $lastWinners,
            'settings' => $settings,
            'allowMulti' => (bool) Yii::app()->params['allow_multi_win'],
        ]);
    }

    public function actionSavePrizes()
    {
        if (!Yii::app()->request->isPostRequest)
            $this->redirect(['admin/index']);

        $tx = Yii::app()->db->beginTransaction();
        try {
            $orders = Yii::app()->request->getPost('prize_order', []);
            $names = Yii::app()->request->getPost('prize_name', []);
            $qtys = Yii::app()->request->getPost('quantity', []);

            foreach ($orders as $id => $ord) {
                $id = (int) $id;
                $ord = (int) $ord;
                $name = trim((string) ($names[$id] ?? ''));
                $qty = (int) ($qtys[$id] ?? 1);

                if ($id > 0 && $name !== '' && $ord > 0 && $qty > 0) {
                    Yii::app()->db->createCommand("
            UPDATE prizes SET prize_order=:o, prize_name=:n, quantity=:q WHERE id=:id
          ")->execute([':o' => $ord, ':n' => $name, ':q' => $qty, ':id' => $id]);
                }
            }

            $newOrd = (int) Yii::app()->request->getPost('new_prize_order', 0);
            $newName = trim((string) Yii::app()->request->getPost('new_prize_name', ''));
            $newQty = (int) Yii::app()->request->getPost('new_quantity', 0);
            if ($newOrd > 0 && $newName !== '' && $newQty > 0) {
                Yii::app()->db->createCommand("
          INSERT INTO prizes (prize_name, prize_order, quantity) VALUES (:n,:o,:q)
        ")->execute([':n' => $newName, ':o' => $newOrd, ':q' => $newQty]);
            }

            $tx->commit();
        } catch (Exception $e) {
            if ($tx->active)
                $tx->rollback();
        }

        $this->redirect(['admin/index']);
    }

    public function actionResetWinners()
    {
        if (Yii::app()->request->isPostRequest) {
            Yii::app()->db->createCommand("DELETE FROM winners")->execute();
        }
        $this->redirect(['admin/index']);
    }

    public function actionUploadParticipants()
    {
        if (!Yii::app()->request->isPostRequest)
            $this->redirect(['admin/index']);

        $file = CUploadedFile::getInstanceByName('csv');
        if (!$file)
            $this->redirect(['admin/index']);

        $fh = fopen($file->tempName, 'r');
        if (!$fh)
            $this->redirect(['admin/index']);

        $header = fgetcsv($fh);
        if (!$header) {
            fclose($fh);
            $this->redirect(['admin/index']);
        }

        $map = array_flip(array_map(fn($s) => trim((string) $s), $header));
        foreach (['code', 'full_name', 'department', 'company'] as $r) {
            if (!isset($map[$r])) {
                fclose($fh);
                exit("CSV thiếu cột: {$r}");
            }
        }

        $tx = Yii::app()->db->beginTransaction();
        try {
            while (($row = fgetcsv($fh)) !== false) {
                $code = trim((string) ($row[$map['code']] ?? ''));
                $name = trim((string) ($row[$map['full_name']] ?? ''));
                $dept = trim((string) ($row[$map['department']] ?? ''));
                $comp = trim((string) ($row[$map['company']] ?? ''));

                if ($code === '' || $name === '')
                    continue;

                $isPartner = isset($map['is_partner']) ? (int) ($row[$map['is_partner']] ?? 0) : 0;

                Yii::app()->db->createCommand("
          INSERT INTO participants (code, full_name, department, company, is_partner, is_active)
          VALUES (:c,:n,:d,:co,:ip,1)
          ON DUPLICATE KEY UPDATE
            full_name=VALUES(full_name),
            department=VALUES(department),
            company=VALUES(company),
            is_partner=VALUES(is_partner),
            is_active=1
        ")->execute([':c' => $code, ':n' => $name, ':d' => $dept, ':co' => $comp, ':ip' => $isPartner]);
            }
            $tx->commit();
        } catch (Exception $e) {
            if ($tx->active)
                $tx->rollback();
        } finally {
            fclose($fh);
        }

        $this->redirect(['admin/index']);
    }

    public function actionSettings()
    {
        if (Yii::app()->request->isPostRequest) {
            $settings = Yii::app()->request->getPost('settings', []);
            foreach ($settings as $name => $val) {
                // Upsert settings
                $exists = Yii::app()->db->createCommand("SELECT count(*) FROM settings WHERE name=:n")->queryScalar([':n' => $name]);
                if ($exists > 0) {
                    Yii::app()->db->createCommand("UPDATE settings SET value=:v WHERE name=:n")->execute([':v' => $val, ':n' => $name]);
                } else {
                    Yii::app()->db->createCommand("INSERT INTO settings (name, value) VALUES (:n, :v)")->execute([':n' => $name, ':v' => $val]);
                }
            }
            Yii::app()->user->setFlash('success', 'Đã lưu cấu hình!');
        }
        $this->redirect(['admin/index']);
    }
    public function actionExportWinners()
    {
        $rows = Yii::app()->db->createCommand("
            SELECT pr.prize_name, p.code, p.full_name, p.department, p.company, w.won_at
            FROM winners w
            JOIN prizes pr ON pr.id = w.prize_id
            JOIN participants p ON p.id = w.participant_id
            WHERE w.confirm = 1
            ORDER BY w.won_at ASC
        ")->queryAll();

        $filename = 'winners_' . date('Y-m-d_H-i') . '.csv';

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . $filename);

        $out = fopen('php://output', 'w');
        // Add BOM for Excel UTF-8 compatibility
        fputs($out, "\xEF\xBB\xBF");

        // Header
        fputcsv($out, ['Giải thưởng', 'Mã NV', 'Họ tên', 'Phòng ban', 'Công ty', 'Thời gian trúng']);

        // Data
        foreach ($rows as $row) {
            fputcsv($out, [
                $row['prize_name'],
                $row['code'],
                $row['full_name'],
                $row['department'],
                $row['company'],
                $row['won_at']
            ]);
        }
        fclose($out);
        Yii::app()->end();
    }
}
