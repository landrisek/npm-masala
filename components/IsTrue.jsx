import {Invalid} from './Invalid.jsx'
import React from 'react'

export class IsTrue extends React.Component {
    IsTrue(props, state) {
        return Invalid(props, this.invalidate(props, state))
    }
}