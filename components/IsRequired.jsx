import {Invalid} from './Invalid.jsx'
import React from 'react'

export class IsRequired extends React.Component {
    IsRequired(props, state) {
        return Invalid(props, this.invalidate(props, undefined == state || 0 == state.length))
    }
}

