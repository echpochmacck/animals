<?php

namespace app\models;

use yii\web\IdentityInterface;
use Yii;

use yii\db\ActiveRecord;
use yii\db\Expression;;

use yii\behaviors\TimestampBehavior;

/**
 * This is the model class for table "users".
 *
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string|null $phone
 * @property string|null $token
 * @property string $password
 * @property string|null $remember_token
 * @property string|null $created_at
 * @property string|null $updated_at
 *
 * @property Orders[] $orders
 */
class Users extends \yii\db\ActiveRecord implements IdentityInterface
{
    public $password_confirmation;
    public $confirm;
    public $register;
    const SCENARIO_REGISTER = 'register';
    const SCENARIO_LOGIN = 'login';
    const SCENARIO_PHONE = 'phone';
    const SCENARIO_EMAIL = 'email';




    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'users';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['name', 'password_confirmation', 'phone', 'email', 'password'], 'required', 'on' => static::SCENARIO_REGISTER],
            [['confirm'], 'match', 'pattern' => '/[01]/'],
            [['email', 'password'], 'required', 'on' => static::SCENARIO_LOGIN],
            [['email'], 'email'],
            [['email'], 'required', 'on' => static::SCENARIO_EMAIL],

            // [['phone'], 'match', 'pattern' => '/\+\d*/'],
            [['created_at', 'updated_at'], 'safe'],
            [['email'], 'unique', 'on' => static::SCENARIO_REGISTER],
            [['password'], 'match', 'pattern' => '/^(?=.*[A-Z])(?=.*[a-z])(?=.*[0-9])[A-Za-z0-9]{3,}$/u', 'on' => static::SCENARIO_REGISTER],
            [['name', 'password', 'password_confirmation'], 'string', 'max' => 255],
            [['email', 'token', 'remember_token'], 'string', 'max' => 100],
            [['phone'], 'string', 'max' => 12],
            [['token'], 'unique'],
            [['phone'], 'required', 'on' => static::SCENARIO_PHONE, 'on' => static::SCENARIO_REGISTER],
            [['phone'], 'unique', 'on' => static::SCENARIO_REGISTER, 'on' => static::SCENARIO_PHONE],


            [['phone'], 'match', 'pattern' => '/^\+?\d+$/', 'message' => 'Номер телефона должен содержать только цифры и может начинаться с знака плюса.', 'on' => [static::SCENARIO_REGISTER, static::SCENARIO_PHONE]],

            [['password_confirmation'], 'compare', 'compareAttribute' => 'password', 'on' => static::SCENARIO_REGISTER],
            [['confirm'], 'required', 'requiredValue' => true, 'on' => static::SCENARIO_REGISTER, 'message' => 'Нужно согласие на обработку данных'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => 'Имя',
            'email' => 'Почта',
            'phone' => 'Телефон',
            'token' => 'Токен',
            'password' => 'Пароль',
            'remember_token' => 'Remember Token',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }

    /**
     * Gets query for [[Orders]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getOrders()
    {
        // return $this->hasMany(Orders::class, ['user_id' => 'id']);
    }

    public static function findIdentity($id)
    {
        return static::findOne($id);
    }

    public static function findIdentityByAccessToken($token, $type = null)
    {
        return static::findOne(['token' => $token]);
    }

    public function getId()
    {
        return $this->id;
    }

    public function getAuthKey()
    {
        // return $this->authKey;
    }

    public function validateAuthKey($authKey)
    {
        // return $this->authKey === $authKey;
    }


    public function behaviors()
    {
        return [
            [
                'class' => TimestampBehavior::class,
                'attributes' => [
                    ActiveRecord::EVENT_BEFORE_INSERT => ['created_at'],
                    // ActiveRecord::EVENT_BEFORE_UPDATE => ['updated_at'],
                ],
                // if you're using datetime instead of UNIX timestamp:
                'value' => new Expression('NOW()'),
            ],
        ];
    }


    public function validPassword($password)
    {
        return yii::$app->getSecurity()->validatePassword($password, $this->password);
    }
}
