import {ApolloClient, HttpLink, InMemoryCache} from 'apollo-client-preset';
import {ApolloProvider, graphql} from 'react-apollo';
import React from 'react';
import ReactDOM from 'react-dom';
import LoginTimer from './components/LoginTimer';
import RoleSelector from './components/RoleSelector';

const userLoggedIn = document.userLoggedIn;

const client = new ApolloClient({
    link: new HttpLink({
        credentials: 'same-origin', // use session cookie
    }),
    cache: new InMemoryCache()
});

if (userLoggedIn !== null) {
    ReactDOM.render(
        <LoginTimer/>,
        document.getElementById('timer')
    );

    ReactDOM.render(
        <ApolloProvider client={client}>
            <RoleSelector link={document.changeRoleLink}/>
        </ApolloProvider>,
        document.getElementById('role')
    );
}
