import {Invalid} from './Invalid.jsx'
import React from 'react'

export class IsDateTime extends React.Component {
    IsDateTime(props, state) {
        return Invalid(props, this.invalidate(props, undefined == state || 0 == state.length || isNaN(Date.parse(state))))
    }
}