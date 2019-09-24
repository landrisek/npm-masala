import Datetime from 'react-datetime/DateTime'
import 'react-datetime/css/react-datetime.css'
import React from 'react'

export class DateTime {
    DateTime(props, state) {
        return <><label style={{marginTop: '10px'}}>{props.label}</label>
            <Datetime id={props.id}
                      locale={'cs'}
                      onChange={this.onChangeDateTime.bind(this, props)}
                      value={state}/></>
    }

    onChangeDateTime(props, state) {
        if ('object' == typeof state) {
            this.setState(this.OnChangeDateTime.bind(this, props, state.format('Y-MM-DD HH:mm:ss')))
        } else {
            this.setState(this.OnChangeDateTime.bind(this, props, state))
        }
    }

    OnChangeDateTime(props, state) {
        return {[props.id]: state}
    }
}
