<?php

namespace app\controllers;

use app\models\Users;
use app\models\Orders;

use Yii;
use yii\filters\auth\HttpBearerAuth;

class UserController extends \yii\rest\Controller
{
    public function behaviors()
    {
        $behaviors = parent::behaviors();

        // remove authentication filter
        $auth = $behaviors['authenticator'];
        unset($behaviors['authenticator']);

        // add CORS filter
        $behaviors['corsFilter'] = [
            'class' => \yii\filters\Cors::class,
            'cors' => [
                // restrict access to
                'Origin' => [(isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : 'http://' . $_SERVER['REMOTE_ADDR'])],
                'Access-Control-Request-Method' => ['POST', 'GET', 'PATCH'],
                'Access-Control-Request-Headers' => ['content-type', 'Authorization'],
            ],
            'actions' => [
                'logout' => [
                    'Access-Control-Allow-Creditials' => true,
                ],
                'user-info' => [
                    'Access-Control-Allow-Creditials' => true,
                ],
                'phone' => [
                    'Access-Control-Allow-Creditials' => true,
                ]
            ]

        ];
        $auth = [
            'class' => HttpBearerAuth::class,
            'only' => ['logout', 'user-info', 'phone', 'email', 'check-orders'],
            // 'optional' => ['logout', 'user-info', 'phone']

        ];
        // re-add authentication filter
        $behaviors['authenticator'] = $auth;
        // avoid authentication on CORS-pre-flight requests (HTTP OPTIONS method)
        return $behaviors;
    }

    public function actions()
    {

        $actions = parent::actions();
        unset($actions['delete'], $actions['create'], $actions['index'], $actions['view'], $actions['apdate']);
        return $actions;
    }
    public $enableCsrfValidation = false;
    public $modelClass = '';


    public function actionRegister()
    {
        $data = Yii::$app->request->post();
        $model = new Users();
        $model->load($data, '');
        $model->scenario = 'register';

        if ($model->validate()) {

            $model->token = yii::$app->security->generateRandomString();
            while (!$model->validate()) {
                $model->token = yii::$app->security->generateRandomString();
            }
            // var_dump('dsd');die;
            $model->password = yii::$app->security->generatePasswordHash($model->password);
            // var_dump($model);die;
            // die;
            $model->save(false);
            yii::$app->response->statusCode = 200;
            $result = [
                'data' => [
                    'status' => 'ok'
                ]
            ];
        } else {
            yii::$app->response->statusCode = 422;
            $result = [
                'error' => [
                    'status' => 'no ok',
                    'errors' => $model->errors,
                ]
            ];
        }
        return $result;
    }

    public function actionLogin()
    {
        $data = yii::$app->request->post();
        $model = new Users();
        $model->load($data, '');
        $model->scenario = 'login';
        if ($model->validate()) {
            $user = Users::findOne(['email' => $model->email]);
            if ($user && $user->validPassword($model->password)) {
                $model = $user;
                $model->token = yii::$app->security->generateRandomString();
                while (!$model->save()) {
                    $model->token = yii::$app->security->generateRandomString();
                }
                yii::$app->response->statusCode = 200;
                $result = [
                    'data' => [
                        'status' => 'authorized',
                        'token' => $model->token,
                    ]
                ];
            } else {
                yii::$app->response->statusCode = 401;
                $result = [
                    'error' => [
                        'status' => 'no ok',
                        'message' => 'Unauthorized',
                    ]
                ];
            }
        } else {
            yii::$app->response->statusCode = 422;
            $result = [
                'error' => [
                    'status' => 'no ok',
                    'errors' => $model->errors,
                ]
            ];
        }
        return $result;
    }
    public function actionLogout()
    {
        $identity = Yii::$app->user->identity;
        $user = Users::findOne($identity->id);
        $user->token = null;
        $user->save(false);
        yii::$app->response->statusCode = 204;
        yii::$app->response->send();
    }

    public function actionUserInfo()
    {
        $identity = Yii::$app->user->identity;
        // var_dump($identity);die;
        $result = [];
        if ($identity) {
            $info = Users::find()
                ->select([
                    'name',
                    'email',
                    'users.id',
                    "DATE_FORMAT(users.created_at, '%d-%m-%Y') AS registrationDate",
                    'COUNT(orders.id) as ordersCount',
                    'DATEDIFF(NOW(), users.created_at) as days',
                    "(SELECT COUNT(*) 
                    FROM ORDERS
                    INNER JOIN statuses on statuses.id = orders.status_id  
                    WHERE user_id = $identity->id and status = 'wasFound') as petsCount"
                ])
                ->leftJoin('orders', 'orders.user_id = users.id')
                ->where(['users.id' => $identity->id])
                ->asArray()
                ->all();
            // var_dump($info);
            // die;
            $result['data']['user'] = $info;
        } else {
            Yii::$app->response->statusCode = 401;
            $result['error'] =  [
                'code' => 401,
                'message ' => 'Unaurhorized'
            ];
        }
        return $result;
    }


    public function actionPhone()
    {
        // $identity = '';
        $identity = Yii::$app->user->identity;
        $result = [];
        if ($identity) {
            $user = Users::findOne($identity->id);
            $data = Yii::$app->request->post();
            $model = new Users();
            $model->scenario = 'phone';
            $model->load($data, '');
            $model->validate();
            if (!$model->hasErrors()) {
                $user->phone = $model->phone;
                $user->save();
                Yii::$app->response->statusCode = 200;
                $result['data'] =  [
                    'status' => 'ok',
                ];
            } else {
                yii::$app->response->statusCode = 422;
                $result = [
                    'error' => [
                        'status' => 'no ok',
                        'errors' => $model->errors,
                    ]
                ];
            }
        } else {
            Yii::$app->response->statusCode = 401;
            $result['error'] =  [
                'code' => 401,
                'message ' => 'Unaurhorized'
            ];
        }
        return $result;
    }
    public function actionEmail()
    {
        // $identity = '';
        $identity = Yii::$app->user->identity;
        $result = [];
        // var_dump($identity);die;
        if ($identity) {
            $user = Users::findOne($identity->id);
            $data = Yii::$app->request->post();
            $model = new Users();
            $model->scenario = 'email';
            $model->load($data, '');
            $model->validate();
            if (!$model->hasErrors()) {
                $user->email = $model->email;
                // var_dump($user);die;
                $user->save(false);
                Yii::$app->response->statusCode = 200;
                $result['data'] =  [
                    'status' => 'ok',
                ];
            } else {
                yii::$app->response->statusCode = 422;
                $result = [
                    'error' => [
                        'status' => 'no ok',
                        'errors' => $model->errors,
                    ]
                ];
            }
        } else {
            Yii::$app->response->statusCode = 401;
            $result['error'] =  [
                'code' => 401,
                'message ' => 'Unaurhorized'
            ];
        }
        return $result;
    }

    public function actionCheckOrders()
    {
        // $identity = '';
        $identity = Yii::$app->user->identity;
        if ($identity) {
            $result = [];
            $user = Users::findOne($identity->id);
            //   var_dump($identity);die;
            $orders = Orders::find()
                ->select([
                    'status',
                    'orders.id',
                    'kind',
                    'photo1',
                    'photo2',
                    'photo3',
                    'description',
                    'mark',
                    'district',
                    'orders.created_at as date'
                ])
                ->innerJoin('pets', 'pets.id = orders.pet_id')
                ->innerJoin('kinds', 'pets.kind_id = kinds.id')
                ->innerJoin('districts', 'districts.id = orders.district_id')
                ->innerJoin('statuses', 'statuses.id = orders.status_id')
                ->where(['orders.user_id' => $identity->id])
                ->asArray()
                ->all();
            // var_dump($orders);
            // die;
            if (!empty($orders)) {

                foreach ($orders as $order) {
                    $result['data'][$order['status']][] = [
                        'id' => $order['id'],
                        'kind' => $order['kind'],
                        'description' => $order['description'],

                        'photos' => array_filter(
                            $order,
                            fn ($value, $key) => str_contains($key, 'photo') && !empty($order[$key]),
                            ARRAY_FILTER_USE_BOTH
                        ),
                        'mark' => $order['mark'],
                        'district' => $order['district'],
                        'date' => $order['date'],
                    ];
                }
                Yii::$app->response->statusCode = 200;
            } else {
                Yii::$app->response->statusCode = 204;
            }
        } else {
            Yii::$app->response->statusCode = 401;
            $result['error'] =  [
                'code' => 401,
                'message ' => 'Unaurhorized'
            ];
        }
        return $result;
    }
}
