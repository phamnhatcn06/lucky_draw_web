<?php

class SiteController extends Controller
{
    public $defaultAction = 'show';

    public function actionIndex()
    {
        $this->render('index');
    }

    public function actionError()
    {
        $error = Yii::app()->errorHandler->error;
        if ($error) {
            echo CHtml::encode($error['message']);
        }
    }

    // SiteController.php
    public function actionShow()
    {
        $currentPrizeId = Yii::app()->db->createCommand("
        SELECT value FROM settings WHERE name='current_prize_id'
    ")->queryScalar();
        $winnerList = Yii::app()->db->createCommand("
        SELECT 
            p.code,
            p.full_name,
            p.department,
            p.id,
            t.prize_id
        FROM winners t
        LEFT JOIN participants p ON t.participant_id = p.id
        WHERE t.prize_id = :id
          AND t.confirm = 1
        ORDER BY t.id ASC
    ")->queryAll(true, [
                    ':id' => $currentPrizeId
                ]);


        $currentPrize = Yii::app()->db->createCommand("SELECT * FROM prizes WHERE id=:id")
            ->queryRow(true, [':id' => $currentPrizeId]);

        $this->render('show', ['winnerList' => $winnerList, 'prize' => $currentPrize]);
    }
}
