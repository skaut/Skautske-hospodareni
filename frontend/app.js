import {ApolloClient, HttpLink, InMemoryCache} from 'apollo-client-preset';
import {ApolloProvider, graphql} from 'react-apollo';
import React from 'react';
import ReactDOM from 'react-dom';
import UserPanel from "./components/UserPanel";

const client = new ApolloClient({
    link: new HttpLink({
        credentials: 'same-origin', // use session cookie
    }),
    cache: new InMemoryCache()
});

ReactDOM.render(
    <ApolloProvider client={client}>
        <UserPanel/>
    </ApolloProvider>,
    document.getElementById('user-panel')
);
