<?php

namespace app\controllers;

use app\models\SignupForm;
use app\models\User;
use app\models\LoginForm;
use yii;
use yii\filters\Cors;
use yii\rest\ActiveController;

class SiteController extends ActiveController
{
    public $modelClass = 'app\models\LoginForm';

    public function actions()
    {
        $actions = parent::actions();
        unset($actions['create']);
        unset($actions['update']);
        unset($actions['delete']);
        unset($actions['view']);
        unset($actions['index']);
        return $actions;
    }

    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $auth = $behaviors['authenticator'];
        unset($behaviors['authenticator']);
        $behaviors['corsFilter'] = [
            'class' => Cors::className(),
            'cors' => [
                'Origin' => ['*'],
                'Access-Control-Request-Method' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'HEAD', 'OPTIONS'],
                'Access-Control-Request-Headers' => ['*'],
            ],
        ];

        $behaviors['authenticator'] = $auth;
        $behaviors['authenticator']['except'] = ['options'];
        $behaviors['authenticator'] = [
            'class' => yii\filters\auth\HttpBearerAuth::className(),
            'except' => [
                'login',
                'signup',
                'index',
                'reset-password',
                'send-email-reset-password',
            ],
        ];

        return $behaviors;
    }

    /**
     * i dex.
     *
     * @return mixed
     */
    public function actionIndex()
    {
        return 'Welcome to yii2 micro api!';
    }

    /**
     * Logs in a user.
     *
     * @return mixed
     */
    public function actionLogin()
    {
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $model = new LoginForm();
        $model->load(\Yii::$app->request->post(), '');
        
        if ($model->login()) {
            return [
                "message"=>"ok"
            ];
        } else {
            Yii::$app->getResponse()->setStatusCode(422);
            return [
                "message"=>"mal"
            ];
        }

    }

    /**
     * Signs user up.
     *
     * @return mixed
     */
    public function actionSignup()
    {
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $model = new SignupForm();
        if ($model->load(\Yii::$app->request->post(), '')) {
            $user = $model->signup();
            return $user;
        }
        return array('error' => "Data Error");
    }

    public function actionResetPassword()
    {
        $params = Yii::$app->request->post();
        $token = $params['token'] ?? "";
        $password = $params['password'] ?? "";
        if (empty($token) || empty($password)) {
            Yii::$app->response->statusCode = 404;
            return [
                'status' => 'error',
                'data' => 'DATA_ERROR',
            ];
        }
        $user = User::findOne([
            'status' => User::STATUS_ACTIVE,
            'password_reset_token' => $token,
        ]);
        if (!$user) {
            Yii::$app->response->statusCode = 404;
            return [
                'status' => 'error',
                'user' => 'NOT_FOUND',
            ];
        }
        $user->setPassword($password);
        $user->removePasswordResetToken();
        if (!$user->save(false)) {
            Yii::$app->response->statusCode = 404;
            return [
                'status' => 'error',
                'password' => 'FAIL_CHANGE_PASSWORD',
            ];
        }
        return [
            'status' => 'success',
            'password' => 'PASSWORD_CHANGE',
        ];
    }

    public function actionSetPassword()
    {
        $params = Yii::$app->request->post();
        $oldPassword = $params['oldPassword'] ?? "";
        $newPassword = $params['newPassword'] ?? "";
        if (empty($oldPassword) || empty($newPassword)) {
            Yii::$app->response->statusCode = 404;
            return [
                'status' => 'error',
                'data' => 'DATA_ERROR',
            ];
        }
        $user = User::findOne([
            'status' => User::STATUS_ACTIVE,
            'id' => yii::$app->user->id,
        ]);
        if (!$user) {
            Yii::$app->response->statusCode = 404;
            return [
                'status' => 'error',
                'user' => 'NOT_FOUND',
            ];
        }
        if (!$user->validatePassword($oldPassword)) {
            Yii::$app->response->statusCode = 404;
            return [
                'status' => 'error',
                'password' => 'WRONG_PASSWORD',
            ];
        }
        $user->setPassword($newPassword);
        if (!$user->save(false)) {
            Yii::$app->response->statusCode = 404;
            return [
                'status' => 'error',
                'password' => 'FAIL_CHANGE_PASSWORD',
            ];
        }

        return [
            'status' => 'success',
            'password' => 'PASSWORD_CHANGE',
        ];
    }

    public function actionSendEmailResetPassword()
    {
        $params = Yii::$app->request->post();
        $email = $params['email'] ?? "";
        if (empty($email)) {
            Yii::$app->response->statusCode = 404;
            return [
                'status' => 'error',
                'data' => 'DATA_ERROR',
            ];
        }
        $user = User::findOne([
            'status' => User::STATUS_ACTIVE,
            'email' => $email,
        ]);
        if (!$user) {
            Yii::$app->response->statusCode = 404;
            return [
                'status' => 'error',
                'user' => 'NOT_FOUND',
            ];
        }
        $user->generatePasswordResetToken();
        if (!$user->save(false)) {
            return [
                'status' => 'error',
                'token' => 'TOKEN_NOT_SAVE',
            ];
        }
        $message = Yii::$app->mailer->compose(
            ['html' => 'passwordResetToken'],
            ['user' => $user]
        )
            ->setFrom('no_reply@clevercbt.com')
            ->setTo($email)
            ->setSubject('Password recovery');

        if (!$message->send()) {
            return [
                'status' => 'error',
                'email' => 'NOT_SEND',
            ];
        }
        return [
            'status' => 'success',
            'email' => 'SEND',
        ];
    }

    protected function verbs()
    {

        return [
            'index' => ['GET', 'HEAD', 'OPTIONS'], //instead of  'index' => ['GET', 'HEAD']
            'login' => ['POST', 'OPTIONS'],
            'register' => ['POST', 'OPTIONS'],
            'reset-password' => ['POST', 'OPTIONS'],
            'set-password' => ['POST', 'OPTIONS'],
            'send-email-reset-password' => ['POST', 'OPTIONS'],
        ];
    }
}
