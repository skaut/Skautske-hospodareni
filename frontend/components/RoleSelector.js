import React from 'react';

export default class RoleSelector extends React.Component {

    constructor(props) {
        super(props);
        this.state = {
            roles: []
        };
        this.handleChange = this.handleChange.bind(this);
    }

    componentDidMount() {
        fetch('/roles', {credentials: 'same-origin'})
            .then((response) => response.json())
            .then((json) => {
                this.setState({
                    roles: json.roles,
                    activeRoleId: json.active_role_id,
                });
            });
    }

    render() {
        const options = this.state.roles.map(
            (role) => <option key={role.id} value={role.id}>{role.name}</option>
        );

        console.log(options);

        return <select onChange={this.handleChange}
                       className="roleSelect input-sm" style={{width: '180px'}}
                       value={this.state.activeRoleId || ''}>{options}</select>
    }

    handleChange(event) {
        window.location.href = this.props.link + "&roleId=" + event.target.value;
    }
}
