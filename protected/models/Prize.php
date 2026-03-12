<?php

class Prize extends CActiveRecord
{
    public function tableName()
    {
        return 'prizes';
    }

    public function rules()
    {
        return [
            ['prize_name, prize_order, quantity', 'required'],
            ['prize_order, quantity, duration, stagger_duration', 'numerical', 'integerOnly' => true],
            ['prize_name', 'length', 'max' => 255],
        ];
    }

    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }
}
