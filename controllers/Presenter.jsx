import React, {Component} from 'react'
import ReactDOM from 'react-dom'

export default class Presenter extends Component {
    constructor(props) {
        super(props)
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