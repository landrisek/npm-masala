import Form from './Form.jsx'
import React, {Component} from 'react'
import ReactDOM from 'react-dom'
import request from 'sync-request'

var ID = 'rowForm'
var LINKS = {}
var ROW = 'row'

export default class RowForm extends Form {
    constructor(props){
        super(props, ID)
        LINKS = JSON.parse(document.getElementById(ID).getAttribute('data-links'))
    }
    submit() {
        var data = this.validate()
        if(null != data) {
            var state = []
            state[ROW] = this.state[ROW]
            state[ROW]._message.Attributes.style = {display:'block'}
            this.setState(state)
            request('POST', LINKS.submit, { json: {Row:data} })
        }
    }
    render() {
        return <div>{this.attached()}</div>
    }
}
ReactDOM.render(<RowForm />, document.getElementById(ID))