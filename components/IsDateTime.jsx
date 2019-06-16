import {Invalid} from './Invalid.jsx'
import React from 'react'

export class IsDateTime extends React.Component {
    IsDateTime(props, state) {
        return Invalid(props, this.invalidate(props, undefined != state && state.length > 0 && isNaN(Date.parse(state))))
    }
}