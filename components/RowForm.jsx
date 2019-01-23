import Form from './Form.jsx'
import React, {Component} from 'react'
import ReactDOM from 'react-dom'
import request from 'sync-request'

var ID = 'rowForm'
var LINKS = {}

export default class RowForm extends Form {
    constructor(props){
        super(props, ID)
        LINKS = JSON.parse(document.getElementById(ID).getAttribute('data-links'))
    }
    temp() {
        if(true == this.validate()) {
            var data = new Object()
            for(var key in this.state) {
                data[key] = this.state[ROW][key].Attributes.value
            }
            var element = this.state.edit;
            element.Attributes.style = {display:'block'}
            var response = JSON.parse(request('POST', LINKS.submit, { json: data }).getBody('utf8'))
            if(undefined != response.message) {
                element.Label = response.message
            }
            if(undefined != response.remove && false != response.remove) {
                document.getElementById(response.remove).remove()
            }
            this.setState({ 'edit': element })
        }
    }
    render() {
        return <div>{this.attached()}</div>
    }
}
ReactDOM.render(<RowForm />, document.getElementById(ID))