<?php

class ApiController extends Controller
{
    public function filters()
    {
        return array();
    }

    private $ACCESS_CODE = 'YEP6868'; // Mã bảo mật truy cập

    public function actionCheckAuth()
    {
        $input = file_get_contents('php://input');
        $data = CJSON::decode($input);
        $code = isset($data['code']) ? $data['code'] : '';

        if ($code === $this->ACCESS_CODE) {
            $this->json(['ok' => true]);
        } else {
            $this->json(['ok' => false, 'msg' => 'Mã truy cập không đúng!']);
        }
    }

    private function json($arr, $code = 200)
    {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code($code);
        echo CJSON::encode($arr);
        Yii::app()->end();
    }

    public function actionPrize()
    {
        $currentPrizeId = Yii::app()->db->createCommand("
        SELECT value FROM settings WHERE name='current_prize_id'
    ")->queryScalar();

        if (!$currentPrizeId) {
            $this->json(['ok' => false, 'msg' => 'Chưa chọn giải']);
            return;
        }

        $row = Yii::app()->db->createCommand("
        SELECT p.*,
               IFNULL(w.c,0) AS awarded,
               p.duration
        FROM prizes p
        LEFT JOIN (
            SELECT prize_id, COUNT(*) c
            FROM winners
            where confirm = 1
            GROUP BY prize_id
        ) w ON w.prize_id = p.id
        WHERE p.id = :id
    ")->queryRow(true, [':id' => $currentPrizeId]);

        if (!$row) {
            $this->json(['ok' => false, 'msg' => 'Giải không tồn tại']);
            return;
        }

        $row['finished'] = ($row['awarded'] >= $row['quantity']);

        $this->json(['ok' => true, 'data' => $row]);
    }


    public function actionStatus()
    {
        $remaining = Yii::app()->db->createCommand("
      SELECT COUNT(*) c
      FROM participants p
      LEFT JOIN winners w ON w.participant_id=p.id
      WHERE p.is_active=1 AND w.participant_id IS NULL
    ")->queryScalar();

        $this->json(array('ok' => true, 'data' => array('remaining' => (int) $remaining)));
    }

    public function actionConfirmWinner()
    {
        $data = json_decode(file_get_contents('php://input'), true);
        Yii::app()->db->createCommand("
        UPDATE winners SET confirm = 1 WHERE participant_id = :uid AND prize_id = :prize_id
      ")->execute(array(':prize_id' => (int) $data['prize_id'], ':uid' => (int) $data['id']));
        $this->json(['ok' => true]);
    }

    public function actionCancelWinner()
    {
        $data = json_decode(file_get_contents('php://input'), true);
        if (isset($data['id']) && $data['id'] == 'undefined') {
            $this->json(['ok' => false, 'msg' => 'Missing params']);
            return;
        }
        if (!isset($data['id']) || !isset($data['prize_id'])) {
            $this->json(['ok' => false, 'msg' => 'Missing params']);
        }

        // Update confirm = 0 để không tính vào giải, nhưng vẫn giữ trong bảng winners để không quay lại vào người này
        Yii::app()->db->createCommand("
            UPDATE winners SET confirm = 0
            WHERE participant_id = :uid AND prize_id = :prize_id
        ")->execute(array(
                    ':prize_id' => (int) $data['prize_id'],
                    ':uid' => (int) $data['id']
                ));

        $this->json(['ok' => true]);
    }

    public function actionNextPrize()
    {
        $currentPrizeId = Yii::app()->db->createCommand("
        SELECT value FROM settings WHERE name='current_prize_id'
    ")->queryScalar();

        $next = Yii::app()->db->createCommand("
        SELECT id FROM prizes
        WHERE prize_order > (
            SELECT prize_order FROM prizes WHERE id = :id
        )
        ORDER BY prize_order ASC
        LIMIT 1
    ")->queryRow(true, [':id' => $currentPrizeId]);

        if (!$next) {
            $this->json(['ok' => false, 'msg' => '🎉 Đã hết toàn bộ giải']);
            return;
        }

        Yii::app()->db->createCommand("
        UPDATE settings SET value=:id WHERE name='current_prize_id'
    ")->execute([':id' => $next['id']]);

        $this->json(['ok' => true]);
    }

    // SPACE -> gọi POST /api/spin (không cần body)
    public function actionSpin()
    {
        $tx = Yii::app()->db->beginTransaction();
        try {
            // 1) Lock + lấy giải chưa đủ
            $prizeId = Yii::app()->db->createCommand("
        SELECT value FROM settings WHERE name='current_prize_id'
    ")->queryScalar();
            $prize = Yii::app()->db->createCommand("
        SELECT p.id, p.prize_name, p.prize_order, p.quantity,p.code,p.duration,
               IFNULL(w.c,0) AS awarded
        FROM prizes p
        LEFT JOIN (
            SELECT prize_id, COUNT(*) c 
            FROM winners 
            GROUP BY prize_id
        ) w ON w.prize_id = p.id
        WHERE p.id = :id
        LIMIT 1
    ")->queryRow(true, [
                        ':id' => $prizeId
                    ]);

            // kiểm tra còn lượt quay không
            $count = Yii::app()->db->createCommand("
        SELECT COUNT(*) FROM winners WHERE prize_id = :id and confirm = 1
    ")->queryScalar([':id' => $prizeId]);

            $limit = Yii::app()->db->createCommand("
        SELECT quantity FROM prizes WHERE id = :id
    ")->queryScalar([':id' => $prizeId]);

            if ($count >= $limit) {
                $this->json([
                    'ok' => false,
                    'msg' => 'Giải này đã quay đủ'
                ]);
                return;
            }

            // 2) Lock + random người chưa trúng
            $winner = Yii::app()->db->createCommand("
        SELECT p.id, p.code, p.full_name, p.department, p.company
        FROM participants p
        LEFT JOIN winners w ON w.participant_id = p.id
        WHERE p.is_active=1 AND w.participant_id IS NULL
        ORDER BY RAND()
        LIMIT 1
        FOR UPDATE
      ")->queryRow();

            if (!$winner) {
                $tx->rollback();
                $this->json(array('ok' => false, 'error' => 'Không còn người hợp lệ'));
            }

            // 3) Insert winner (DB UNIQUE đảm bảo chỉ trúng 1 lần)
            Yii::app()->db->createCommand("
        INSERT INTO winners (prize_id, participant_id,confirm) VALUES (:pid,:uid,0)
      ")->execute(array(':pid' => (int) $prize['id'], ':uid' => (int) $winner['id']));

            $tx->commit();

            $this->json(array(
                'ok' => true,
                'data' => array(
                    'prize' => array('id' => (int) $prize['id'], 'name' => $prize['prize_name'], 'code' => $prize['code'], 'duration' => (int) $prize['duration']),
                    'winner' => array(
                        'id' => $winner['id'],
                        'code' => $winner['code'],
                        'full_name' => $winner['full_name'],
                        'department' => $winner['department'],
                        'company' => $winner['company'],
                    ),
                )
            ));
        } catch (Exception $e) {
            if ($tx->active)
                $tx->rollback();
            $this->json(array('ok' => false, 'error' => 'Spin failed'), 500);
        }
    }
}
