import { gql } from 'graphql-tag';
import React from 'react';

export default class RoleSelector extends React.Component {

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
                       className="roleSelect input-sm" style={{width: '180px'}}
                       value={user.activeRoleId || ''}>{options}</select>)
    }

    handleChange(event) {
        window.location.href = document.changeRoleLink + "&roleId=" + event.target.value;
    }
}
