import React from "react";

export default class LoginTimer extends React.Component {

    constructor(props) {
        super(props);
        this.state = {percentage: 100, color: 'success'};
    }

    render() {
        return <div className="progress" style={{height: 7 + 'px'}}>
            <div style={{width: this.state.percentage + '%'}}
                 role="progressbar"
                 className={"progress-bar progress-bar-" + this.state.color}/>
        </div>
    }

    componentDidMount() {
        this.timer = setInterval(() => this.tick(), 60 * 1000)
    }

    tick() {
        const percentage = this.state.percentage - 1;

        this.setState({
            percentage: percentage,
            color: percentage > 33 ? 'success' : 'danger',
        })
    }
}
