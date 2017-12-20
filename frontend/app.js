import React from 'react';
import ReactDOM from 'react-dom';
import LoginTimer from './components/LoginTimer';
import RoleSelector from './components/RoleSelector';

const userLoggedIn = document.userLoggedIn;

if (userLoggedIn !== null) {
    ReactDOM.render(
        <LoginTimer/>,
        document.getElementById('timer')
    );

    ReactDOM.render(
        <RoleSelector link={document.changeRoleLink}/>,
        document.getElementById('role')
    );
}
