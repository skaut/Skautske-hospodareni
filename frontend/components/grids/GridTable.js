import React from 'react';

export default function Loader(props) {
    return (
        <table className="table table-striped">
            {props.rows}
        </table>
    );
}
