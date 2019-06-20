import Datetime from 'react-datetime/DateTime'
import 'react-datetime/css/react-datetime.css'
import React from "react";

export class DateTimeFrom {
    DateTimeFrom(props, state) {
        return <><label style={{marginTop:'10px'}}>{props.label}</label>
            <Datetime id={props.id}
                      locale={'cs'}
                      onChange={this.onChangeDateTimeFrom.bind(this, props)}
                      value={state} /></>
    }
    onChangeDateTimeFrom(props, state) {
        if('object' == typeof state) {
            this.state.Where[props.id + ' >='] = state.format('Y-MM-DD HH:mm:ss')
        } else {
            this.state.Where[props.id + ' >='] = state
        }
        this.setState({Where:this.state.Where})
    }
}