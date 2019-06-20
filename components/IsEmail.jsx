import {Invalid} from './Invalid.jsx'
import React from 'react'

export class IsEmail extends React.Component {
    IsEmail(props, state) {
        return Invalid(props, this.invalidate(props,
            undefined != state && state.length > 0 && false == /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/.test(String(state).toLowerCase())))
    }
}