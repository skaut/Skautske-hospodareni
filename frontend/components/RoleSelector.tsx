import gql from 'graphql-tag';
import { graphql } from "react-apollo/index";
import * as React from 'react';
import { User } from "./model/types";

type RoleSelectorProps = {
    user: User;
    mutate?: (options: object) => Promise<any>;
}

class RoleSelector extends React.Component<RoleSelectorProps, {}> {

    constructor(props: RoleSelectorProps) {
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
                       defaultValue={""+user.activeRoleId || ''}>{options}</select>)
    }

    handleChange(event: any) {
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

const wrap = graphql<boolean, RoleSelectorProps>(query, {options: {notifyOnNetworkStatusChange: true}});

export default wrap(RoleSelector);
