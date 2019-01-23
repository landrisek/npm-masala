import React, {Component} from 'react'
import ReactDOM from 'react-dom'

export default class Presenter extends Component {
    constructor(props) {
        super(props)
        this.state = JSON.parse(document.getElementById(props.state).getAttribute('state'))
    }
    Build(props) {
        for(var key in props) {
            if('object' == typeof(props[key])) {
                props[key].id = key 
            }
        }
        return props
    }
}