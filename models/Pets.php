<?php

namespace app\models;

use yii\web\UploadedFile;


use Yii;
use yii\helpers\VarDumper;

/**
 * This is the model class for table "pets".
 *
 * @property int $id
 * @property string $mark
 * @property string $photo1
 * @property string $photo2
 * @property string $photo3
 * @property string $description
 * @property int $kind_id
 *
 * @property Kinds $kind
 * @property Orders[] $orders
 */
class Pets extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     * 
     */
    const SCENARIO_UPDATE = 'update';
    const SCENARIO_MAKE = 'make';
    public $file1;
    public $file2;
    public $file3;


    public static function tableName()
    {
        return 'pets';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [

            [['description', 'photo1'], 'required', 'on' => static::SCENARIO_UPDATE],
            [['description', 'photo1', 'kind_id'], 'required', 'on' => static::SCENARIO_MAKE],

            [['mark'], 'safe', 'on' => static::SCENARIO_UPDATE],
            [['kind_id'], 'integer',  'on' => static::SCENARIO_MAKE],

            [['mark'], 'string', 'max' => 100],
            [['photo1', 'photo2', 'photo3'], 'file', 'extensions' => ['png'], 'maxSize' => 2 * 1024 * 1024],


        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'mark' => 'Mark',
            'photo1' => 'Photo1',
            'photo2' => 'Photo2',
            'photo3' => 'Photo3',
            'description' => 'Description',
            'kind_id' => 'Kind ID',
        ];
    }

    /**
     * Gets query for [[Kind]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getKind()
    {
        return $this->hasOne(Kinds::class, ['id' => 'kind_id']);
    }

    /**
     * Gets query for [[Orders]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getOrders()
    {
        return $this->hasMany(Orders::class, ['pet_id' => 'id']);
    }

    public function upload($field, $pet)
    {
        if ($this->$field) {
            $dir = Yii::getAlias('@app') . '/uploads/';
            // VarDumper::dump($this->$field->baseName, 10, true);die;
            $filePath = $dir . $this->$field->baseName . '.' . $this->$field->extension;
            if ($this->$field->saveAs($filePath)) {
                $pet->$field = Yii::$app->request->getHostInfo() . '/uploads/' .  $this->$field->baseName . '.' . $this->$field->extension;
            }
        }
    }
}
