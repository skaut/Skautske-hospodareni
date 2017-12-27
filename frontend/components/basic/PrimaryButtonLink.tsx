import * as React from 'react';

interface PrimaryButtonProps
{
    link: string,
    text: string,
}

export default class PrimaryButtonLink extends React.Component<PrimaryButtonProps, {}> {

    render() {
        return <a href={this.props.link} className="btn btn-primary btn-sm">{this.props.text}</a>;
    }

}
