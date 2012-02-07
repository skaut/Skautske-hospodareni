<?php

class LoginFormControl extends Control {

    public function logOut() {
        Environment::getUser()->logout(TRUE);
        $this->presenter->flashMessage('Byl jsi úspěšne odhlášen.');
    }

    public function render() {
        echo $this['form'];
    }


    public function createComponentForm($name) {
        $form = new AppForm($this, $name);
        $form->addText('username', 'Jméno:')
                ->addRule(Form::FILLED, 'Musíte vyplňit přihlašovací jméno.');

        $form->addPassword('password', 'Heslo:')
                ->addRule(Form::FILLED, 'Musíte vyplňit heslo.');
        $form->addHidden("key");

        $form->addSubmit('login', 'Login');

        $form->addProtection('Odešlete přihlašovací údaje znovu. Vypršel bezpečnostní časový limit.');

        $form->onSuccess[] = array($this, 'loginFormSubmitted');

        return $form;
    }

    public function loginFormSubmitted(AppForm $form) {
        try {
            $backlink = $form['key']->getValue();
            $user = Environment::getUser();
            $user->setExpiration('+ 2 hours');// nastavíme expiraci
            $user->login($form['username']->getValue(), $form['password']->getValue());

            $this->presenter->getApplication()->restoreRequest($backlink);
            //$this->presenter->redirect(':Default:default');
            $this->presenter->redirect(':Default:');


        } catch (AuthenticationException $e) {
            $form->addError($e->getMessage());
        }
    }

}
