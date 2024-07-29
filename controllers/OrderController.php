<?php

namespace app\controllers;

use Yii;
use yii\filters\auth\HttpBearerAuth;
use app\models\Orders;
use app\models\Statuses;
use app\models\Pets;
use app\models\Districts;
use app\models\Subscriptions;
use app\models\Users;
use Codeception\Scenario;
use yii\web\UploadedFile;
use app\models\Kinds;
use yii\helpers\VarDumper;

class OrderController extends \yii\rest\Controller
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
                'Access-Control-Request-Method' => ['POST', 'GET', 'PATCH', 'DELETE'],
                'Access-Control-Request-Headers' => ['content-type', 'Authorization'],
            ],
            'actions' => [
                'user-info' => [
                    'Access-Control-Allow-Creditials' => true,
                ],
                'delete' => [
                    'Access-Control-Allow-Creditials' => true,
                ],
                'rewrite' => [
                    'Access-Control-Allow-Creditials' => true,
                ]
            ]

        ];
        $auth = [
            'class' => HttpBearerAuth::class,
            'only' => ['user-info', 'delete', 'rewrite'],
            // 'optional' => ['user-info', 'delete'],   

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

    public function actionQuick()
    {
        // var_dump('я тут');die;
        $data = yii::$app->request->get();
        $result = [];
        $result['data']['orders'] = [];
        if (isset($data['description'])) {
            $description = $data['description'];
            // var_dump($description);die;
            $orders = Orders::find()
                ->select([
                    'orders.id',
                    'kind',
                    'description',
                    'mark',
                    'district',
                    'created_at as date',
                    'photo1',
                    'photo1',
                    'photo2',
                    'photo3',

                ])
                ->innerJoin('pets', 'pets.id = orders.pet_id')
                ->innerJoin('kinds', 'pets.kind_id = kinds.id')
                ->innerJoin('districts', 'districts.id = orders.district_id')
                ->where(['like', 'pets.description', $data['description']])
                ->asArray()
                ->all();

            foreach ($orders as $order) {
                $result['data']['orders'][] =
                    [
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
                        'date' => $order['date']


                    ];
            }
        }
        Yii::$app->response->statusCode = 204;
        if (!empty($result))
            Yii::$app->response->statusCode = 200;
        return $result;
    }

    public function actionSearch()
    {
        // var_dump('я тут');die;
        $data = yii::$app->request->get();
        $result = [];
        $result['data']['orders'] = [];
        $district = (isset($data['district']) ? $data['district'] : null);
        $kind = (isset($data['kind']) ? $data['kind'] : null);
        $orders = Orders::find()
            ->select([
                'orders.id',
                'kind',
                'description',
                'mark',
                'district',
                'created_at as date',
                'photo1',
                'photo1',
                'photo2',
                'photo3',

            ])
            ->innerJoin('pets', 'pets.id = orders.pet_id')
            ->innerJoin('kinds', 'pets.kind_id = kinds.id')
            ->innerJoin('districts', 'districts.id = orders.district_id')
            ->filterWhere([
                'like', 'kinds.kind', $kind,
            ])
            ->andFilterWhere([
                'districts.district' => $district,
            ])
            ->asArray()
            ->all();
        // VarDumper::dump($orders->createCommand()->rawSql, 10, true); die;
        foreach ($orders as $order) {
            $result['data']['orders'][] =
                [
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
                    'date' => $order['date']


                ];
        }
        Yii::$app->response->statusCode = 204;
        if (!empty($result))
            Yii::$app->response->statusCode = 200;
        return $result;
    }


    public function actionPets()
    {
        $data = yii::$app->request->get();
        $result = [];
        $result['data']['orders'] = [];
        $district = (isset($data['district']) ? $data['district'] : null);
        $kind = (isset($data['kind']) ? $data['kind'] : null);
        $orders = Orders::find()
            ->select([
                'orders.id',
                'kind',
                'description',
                'mark',
                'district',
                'created_at as date',
                'photo1',
                'photo1',
                'photo2',
                'photo3',
                'orders.user_id'

            ])
            ->innerJoin('pets', 'pets.id = orders.pet_id')
            ->innerJoin('kinds', 'pets.kind_id = kinds.id')
            ->innerJoin('districts', 'districts.id = orders.district_id')
            ->orderBy('orders.created_at DESC')
            ->limit(6)
            ->asArray()
            ->all();

        foreach ($orders as $order) {
            $result['data']['orders'][] =
                [
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
                    'registered' => !empty($order['user_id'])


                ];
        }
        Yii::$app->response->statusCode = 204;
        if (!empty($result))
            Yii::$app->response->statusCode = 200;
        return $result;
    }

    public function actionSub()
    {
        $data = Yii::$app->request->post();
        $sub = new Subscriptions();
        $result = [];
        $sub->load($data, '');
        $sub->validate();
        if (!$sub->hasErrors()) {
            $sub->save();
            Yii::$app->response->statusCode = 200;
            $result['data'] = [
                'status' => 'ok',
            ];
        } else {
            Yii::$app->response->statusCode = 422;
            $result['error'] = [
                'code' => '422',
                'message' => 'Validation Error',
                'errors' => $sub->errors
            ];
        }
        return $result;
    }

    public function actionheckOrders()
    {
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

    public function actionDelete($order_id = null)
    {

        $identity = Yii::$app->user->identity;
        $result = [];
        if ($identity) {
            // var_dump($identity);
            $order = Orders::findOne($order_id);
            if ($order) {
                if (($order->status_id == 1 || $order->status_id == 2) && $order->user_id == $identity->id) {
                    $order->delete();
                    Yii::$app->response->statusCode = 200;
                    $result['data'] =  [
                        'status' => 'ok',
                    ];
                } else {
                    Yii::$app->response->statusCode = 401;
                    $result['error'] =  [
                        'code' => 403,
                        'message ' => 'Access denied'
                    ];
                }
            } else {
                Yii::$app->response->statusCode = 404;
                $result['error'] =  [
                    'code' => 401,
                    'message ' => 'Not '
                ];
            }
            return $result;
        }
    }

    public function actionRewrite($order_id = null)
    {

        $identity = Yii::$app->user->identity;
        $result = [];
        if ($identity) {
            // var_dump($identity);
            $order = Orders::findOne($order_id);
            if ($order) {
                if (($order->status_id == 1 || $order->status_id == 2) && $order->user_id == $identity->id) {
                    $pet = Pets::findOne($order->pet_id);
                    $model = new Pets();
                    $model->scenario = 'update';
                    if ($model->load($this->request->post(), '')) {


                        $model->photo1 = UploadedFile::getInstanceByName('photo1');
                        $model->photo2 = UploadedFile::getInstanceByName('photo2');
                        $model->photo3 = UploadedFile::getInstanceByName('photo3');

                        if ($model->validate()) {

                            $model->upload('photo1', $pet);
                            $model->upload('photo2', $pet);
                            $model->upload('photo3', $pet);

                            $pet->description = $model->description;
                            $model->mark ?  $pet->mark = $model->mark : null;

                            $pet->save(false);

                            Yii::$app->response->statusCode = 200;
                            $result['data'] =  [
                                'status' => 'ok',
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
                } else {
                    Yii::$app->response->statusCode = 401;
                    $result['error'] =  [
                        'code' => 403,
                        'message ' => 'Access denied'
                    ];
                }
            } else {
                Yii::$app->response->statusCode = 404;
                $result['error'] =  [
                    'code' => 401,
                    'message ' => 'Not '
                ];
            }
            return $result;
        }
    }
    public function actionAnimalCard($order_id = null)
    {
        $result = [];
        $order = Orders::find()
            ->select([
                'orders.id',
                'kind',
                'description',
                'mark',
                'district',
                'orders.created_at as date',
                'photo1',
                'photo2',
                'photo3',
                'orders.user_id'

            ])
            ->innerJoin('pets', 'pets.id = orders.pet_id')
            ->innerJoin('kinds', 'pets.kind_id = kinds.id')
            ->innerJoin('districts', 'districts.id = orders.district_id')
            ->innerJoin('users', 'users.id = orders.user_id')
            ->orderBy('orders.created_at DESC')
            ->where(['orders.id' => $order_id])
            ->asArray()
            ->one();
        $arr = [];
        if ($order) {


            $result['data']['pet'][] =
                [
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
                    'registered' => !empty($order['user_id'])


                ];

            yii::$app->response->statusCode = 200;
            return $result;
        } else {
            yii::$app->response->statusCode = 204;
        }
    }

    public function actionNew()
    {
        $data = Yii::$app->request->post();
        $user = new Users();
        $pet = new Pets();
        $pet->scenario = 'make';
        // var_dump($pet->description);die;
        $pet->photo1 = UploadedFile::getInstanceByName('photo1');
        $pet->photo2 = UploadedFile::getInstanceByName('photo2');
        $pet->photo3 = UploadedFile::getInstanceByName('photo3');
        // var_dump($user);die;
        $pet->kind_id = Kinds::findOne(['kind' => $data['kind']])->id;
        if ($data['register']) {
            $user->scenario = 'register';
        } else {
            $user->scenario = 'login';
        }
        $user->load($data, '');
        $pet->load($data, '');
        $user->validate();
        $pet->validate();
        // var_dump($user);
        // die;
        if ($user->validate() && $pet->validate()) {

            if ($data['register']) {
                var_dump('ds');
                $user->password = yii::$app->security->generatePasswordHash($user->password);
                $user->save(false);
                // $user = Users::findOne(['email' => $user->email]);
            } else {
                $user = Users::findOne(['email' => $data['email']]);
                // var_dump($user);die;
                if ($user && $user->validPassword($data['password'])) {
                    $model = $user;
                    $model->token = yii::$app->security->generateRandomString();
                    while (!$model->save()) {
                        $model->token = yii::$app->security->generateRandomString();
                    }
                } else {
                    yii::$app->response->statusCode = 401;
                    $result = [
                        'error' => [
                            'status' => 'no ok',
                            'message' => 'Unauthorized',
                        ]
                    ];
                    return $result;
                }
            }

            $pet->upload('photo1', $pet);
            $pet->upload('photo2', $pet);
            $pet->upload('photo3', $pet);
            // die;
            // VarDumper::dump($pet->attributes, 10, true);
            $pet->save(false);
            // VarDumper::dump($pet->attributes, 10, true);
            // die;

            $order = new Orders();
            $order->pet_id = $pet->id;

            $order->user_id = $user->id;
            $order->district_id = Districts::findOne(['district' => $data['district']])->id;
            $order->status_id = Statuses::findOne(['status' => 'onModeration'])->id;
            $order->save(false);

            $result['data'] = [
                'status' => 'ok',
                'id' => Orders::findOne(['user_id' => $user->id, 'pet_id' => $pet->id])->id
            ];
            yii::$app->response->statusCode = 200;
            // return $result;

        } else {
            yii::$app->response->statusCode = 422;
            $result = [
                'error' => [
                    'status' => 'no ok',
                    'errors' => $user->errors ? $user->errors : $pet->errors,
                ]
            ];
        }
        return $result;
    }
}
