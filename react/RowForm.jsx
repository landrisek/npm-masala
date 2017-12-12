import Form from './Form.jsx'
import React, {Component} from 'react'
import ReactDOM from 'react-dom'
import request from 'sync-request'

var LINKS = {}

export default class RowForm extends Form {
    constructor(props){
        super(props)
        var name = this.constructor.name;
        LINKS = JSON.parse(document.getElementById(name[0].toLowerCase() + name.substring(1, name.length)).getAttribute('data-links'));
    }
    temp() {
        if(true == this.validate()) {
            var data = new Object()
            for(var key in this.state) {
                data[key] = this.state[ROW][key].Attributes.value
            }
            var element = this.state.edit;
            element.Attributes.style = {display:'block'}
            var response = JSON.parse(request('POST', LINKS['submit'], { json: data }).getBody('utf8'))
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
ReactDOM.render(<RowForm />, document.getElementById('rowForm'))