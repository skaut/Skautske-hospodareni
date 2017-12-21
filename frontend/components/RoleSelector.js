import { gql } from 'graphql-tag';
import { graphql } from 'react-apollo';
import React from 'react';
import { ActivityIndicator } from 'react';

class RoleSelector extends React.Component {

    constructor(props) {
        super(props);
        this.handleChange = this.handleChange.bind(this);
    }

    render() {
        if(this.props.data.loading) {
            return <i className="fa fa-circle-o-notch" style={{color: 'white'}}/>
        }

        const user = this.props.data.user;

        const options = user.roles.map(
            (role) => <option key={role.id} value={role.id}>{role.name}</option>
        );

        return (<select onChange={this.handleChange}
                       className="roleSelect input-sm" style={{width: '180px'}}
                       value={user.activeRoleId || ''}>{options}</select>)
    }

    handleChange(event) {
        window.location.href = this.props.link + "&roleId=" + event.target.value;
    }
}

const query = gql`    
        query CurrentUser {
            user {
                roles {
                    id,
                    name
                }
                activeRoleId
            }   
        }
`;

export default graphql(query, {options: { notifyOnNetworkStatusChange: true } })(RoleSelector);
