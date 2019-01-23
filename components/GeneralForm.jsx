import Form from './Form.jsx'
import React, {Component} from 'react'
import ReactDOM from 'react-dom'

var LINKS = {}
var ROW = 'row'

export default class GeneralForm extends Form {
    constructor(props){
        super(props)
        var name = this.constructor.name;
        LINKS = JSON.parse(document.getElementById(name[0].toLowerCase() + name.substring(1, name.length)).getAttribute('data-links'));
    }
    render() {
        return <div>{this.attached()}</div>
    }
}
ReactDOM.render(<GeneralForm />, document.getElementById('generalForm'))