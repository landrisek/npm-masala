import {Invalid} from './Invalid.jsx'
import React from 'react'

export class IsLower extends React.Component {
    IsLower(props, state) {
        return Invalid(props, this.invalidate(props,undefined == state || props.value > state.length))
    }
}