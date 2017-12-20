import React from 'react';
import ReactDOM from 'react-dom';
import LoginTimer from './components/LoginTimer';

const timer = document.getElementById('timer');
if (timer !== null) {
    ReactDOM.render(
        <LoginTimer/>,
        timer
    );
}
