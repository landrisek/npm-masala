import {Invalid} from './Invalid'
import React from './react'

export class IsEqual extends React.Componet {
    IsEqual(props, state, value) {
        return Invalid(props, this.invalidate(props, state != value))
    }
}