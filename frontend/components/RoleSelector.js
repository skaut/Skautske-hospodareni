import { gql } from 'graphql-tag';
import {graphql} from "react-apollo/index";
import React from 'react';

class RoleSelector extends React.Component {

    constructor(props) {
        super(props);
        this.handleChange = this.handleChange.bind(this);
    }

    render() {
        const user = this.props.user;

        const options = user.roles.map(
            (role) => <option key={role.id} value={role.id}>{role.name}</option>
        );

        return (<select onChange={this.handleChange}
                       className="input-sm roleSelect" style={{width: '180px'}}
                       value={user.activeRoleId || ''}>{options}</select>)
    }

    handleChange(event) {
        this.props.mutate({
            variables: {
                roleId: parseInt(event.target.value),
            }
        }).then(({ data }) => {
            console.log('Role changed');
            location.reload();
        }).catch((error) => {
            console.log("Couldn't change role", error);
            location.reload();
        });
    }
}


const query = gql`
    mutation ChangeRole($roleId: Int!) {
        changeRole(roleId: $roleId)
    }
`;

export default graphql(query, {options: {notifyOnNetworkStatusChange: true}})(RoleSelector);
