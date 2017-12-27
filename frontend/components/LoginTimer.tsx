import * as React from "react";

interface LoginTimerState
{
    percentage: number,
    color: string,
}

export default class LoginTimer extends React.Component<{}, LoginTimerState> {

    constructor(props: object) {
        super(props);
        this.state = {percentage: 100, color: 'success'};
    }

    timer: number;

    render() {
        return (
            <div className="nav visible-lg" style={{ width: '100px', marginTop: '12px' }}>
                <div className="progress" style={{height: 7 + 'px'}}>
                <div style={{width: this.state.percentage + '%'}}
                    role="progressbar"
                    className={"progress-bar progress-bar-" + this.state.color}/>
                </div>
            </div>);
    }

    componentDidMount() {
        this.timer = window.setInterval(() => this.tick(), 60 * 1000)
    }

    tick() {
        const percentage = this.state.percentage - 1;

        this.setState({
            percentage: percentage,
            color: percentage > 33 ? 'success' : 'danger',
        })
    }
}
