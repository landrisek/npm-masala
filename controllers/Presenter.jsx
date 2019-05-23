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

export function build(id, callback) {
    let controller = document.getElementById(id)
    fetch(controller.getAttribute('data-link'),
        {body: JSON.stringify({}),
        headers: {Accept: 'application/json',
                  'Access-Control-Allow-Origin':'*',
                  'Access-Control-Request-Headers': 'access-control-allow-origin,content-type',
                  'Content-Type': 'application/json'}, 
        method:'POST'}).then(response => response.json()).then(props => {
        callback(controller, props)
    }).catch(error => { console.log(error) })
}