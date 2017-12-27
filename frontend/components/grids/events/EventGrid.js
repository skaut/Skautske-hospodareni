import React from "react";
import {gql} from "graphql-tag";
import {graphql} from "react-apollo/index";
import GridTable from '../GridTable';
import Loader from '../../Loader';
class EventGrid extends React.Component {
    render() {
        if (this.props.data.loading) {
            return <Loader/>;
        }

        const events = this.props.data.events;
        const rows = events.map((event) => {
            return (<tr key={event.id}>
                <td>{event.id}</td>
                <td>{event.name}</td>
                <td>{event.state}</td>
            </tr>);
        });
console.log(rows);
        return <GridTable rows={rows}/>;
    }
}

const query = gql`    
    query Events {
        events {
            id,
            name
        }
    }
`;

export default graphql(query, {options: {notifyOnNetworkStatusChange: true}})(EventGrid);
