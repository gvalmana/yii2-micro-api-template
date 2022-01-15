<?php

namespace app\models;

use app\models\User;
use yii\base\Model;
use Yii;

/**
 * Signup form
 */
class SignupForm extends Model
{

    public $username;
    public $email;
    public $password;
    public $password_confirm;
    public $nombre_usuario;
    public $apellido_usuario;

    public function __construct($config = [])
    {
        $this->attributes = parent::attributes();
        parent::__construct($config);
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            ['username', 'trim'],
            ['username', 'required'],
            ['username', 'unique', 'targetClass' => '\app\models\User', 'message' => 'El nombre de usuario ya existe.'],
            ['username', 'string', 'min' => 2, 'max' => 255],

            ['email', 'trim'],
            ['email', 'required'],
            ['email', 'email'],
            ['email', 'string', 'max' => 255],
            ['email', 'unique', 'targetClass' => '\app\models\User', 'message' => 'Existe una cuenta con ese correo electrónico asociado.'],

            ['password', 'required'],
            ['password', 'string', 'min' => Yii::$app->params['user.passwordMinLength']],
            ['password', 'compare',  'compareAttribute'=>'password_confirm', 'operator'=> '===', 'message'=>'Las contraseñas no coinciden.'],

            ['password_confirm','required']
        ];
    }

    /**
     * Signs user up.
     *
     * @return bool whether the creating new account was successful and email was sent
     */
    public function signup()
    {
        if (!$this->validate()) {
            return array('errors'=>$this->errors);
        }
        
        $user = new User();
        $user->username = $this->username;
        $user->email = $this->email;
        $user->setPassword($this->password);
        $user->generateAuthKey();
        $user->generateEmailVerificationToken();
        if ($user->save()) {
            //$this->sendEmail($user);
            return ['data'=> $user];
        }else{
            return array('errors'=>$user->errors);
        }
    }    

    /**
     * Sends confirmation email to user
     * @param User_rbac $user user model to with email should be send
     * @return bool whether the email was sent
     */
    public function sendEmail($user)
    {
        $mailer = Yii::$app->mailer;
        $mailer->htmlLayout = '@common/mail/layouts/html';
        $mailer->textLayout = '@common/mail/layouts/text';
        return $mailer
            ->compose(
                ['html' => '@common/mail/emailVerify-html', 'text' => '@common/mail/emailVerify-text'],
                ['user' => $user]
            )
            ->setFrom([Yii::$app->params['supportEmail'] => Yii::$app->name . ' robot'])
            ->setTo($user->email)
            ->setSubject('Account registration at ' . Yii::$app->name)
            ->send();
    }
}

