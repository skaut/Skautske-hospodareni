import React from 'react';

export default class PrimaryButtonLink extends React.Component {

    render() {
        return <a href={this.props.link} className="btn btn-primary btn-sm">{this.props.text}</a>;
    }

}
