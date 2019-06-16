import {Invalid} from './Invalid.jsx'
import React from 'react'

export class IsPhoneNumber extends React.Component {
    IsPhoneNumber(props, state) {
        return Invalid(props, this.invalidate(props, undefined != state && state.length > 0 && false == /^[\+]?[(]?[0-9]{3}[)]?[-\s\.]?[0-9]{3}[-\s\.]?[0-9]{3,6}$/im.test(state)))
    }
}