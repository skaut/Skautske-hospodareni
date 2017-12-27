import * as React from "react";
import Loader from "./Loader";
import PrimaryButtonLink from "./basic/PrimaryButtonLink";
import RoleSelector from "./RoleSelector";
import gql from "graphql-tag";
import {ChildProps, graphql} from "react-apollo/index";
import LoginTimer from "./LoginTimer";
import {User} from "./model/types";

type UserPanelProps = {
    data: {
        loading: boolean;
        user: User;
    },
    mutate?: any
}

class UserPanel extends React.Component<ChildProps<UserPanelProps, Response>>
{

    render() {
        if (this.props.data.loading) {
            return <Loader/>;
        }

        const user = this.props.data.user;

        if (user.loggedIn) {
           return (
               <div className="navbar-form" style={{paddingLeft: '4px', width: '395px'}}>
                   <div className="pull-left">
                        <LoginTimer/>
                   </div>
                   <div className="pull-right">
                       <RoleSelector user={user}/>
                       <PrimaryButtonLink link={user.logoutLink} text="Odhlásit se" />
                   </div>
               </div>
           );
        }

        const loginLink = user.loginLink + '?backlink=' + encodeURIComponent(window.location.href);

        return (<p className="navbar-btn pull-right">
            <PrimaryButtonLink link={loginLink} text="Přihlásit se"/>
        </p>)
    }
}

const query = gql`
    query CurrentUser {
        user {
            loggedIn
            loginLink
            logoutLink
            activeRoleId
            roles {
                id,
                name
            }
        }
    }
`;

type Response = {
    data: {
        user: User;
    }
    user: User,
    mutate?: any;
}

const wrap = graphql(query, {options: {notifyOnNetworkStatusChange: true}});

export default wrap(UserPanel);
