import axios from 'axios'
import React, {Component} from 'react'
import ReactDOM from 'react-dom'
import Form from './Form.jsx'

var ROW = 'row'
var LINKS = {}

export default class ImportForm extends Form {
    constructor(props){
        super(props)
        var name = this.constructor.name;
        LINKS = JSON.parse(document.getElementById(name[0].toLowerCase() + name.substring(1, name.length)).getAttribute('data-links'));
    }
    done(payload) {
        super.done(payload)
        console.log(payload)
        var element = this.state[ROW]._done
        element.Attributes.style = {display:'block'}
        var state = []
        state[ROW] = this.state[ROW]
        state[ROW]._done = element
        this.setState(state)
    }
    prepare(event) {
        this.run(this.state[ROW][event.target.id].Attributes.data, event.target.id + '-progress')
    }
    submit() {
        var data = this.validate()
        if(null != data) {
            var submit = this.state[ROW]._submit
            var file = this.state[ROW]._file
            var data = new Object()
            var state = []
            state[ROW] = this.state[ROW];
            for (var upload in state[ROW]._file.Attributes.value) break;
            data._file = upload
            submit.Attributes.style = {display:'none'}
            file.Attributes.style = {display:'none'}
            state[ROW]._submit = submit
            state[ROW]._file = file
            this.setState(state)
            var prepare = this.state[ROW]._prepare
            prepare.Attributes.className = 'btn btn-success disabled'
            prepare.Attributes.style = {display:'block'}
            axios.post(LINKS['import'], data).then(response => {
                prepare.Attributes.data = response.data
                prepare.Attributes.className = 'btn btn-success'
                state[ROW] = this.state[ROW]
                state[ROW]._prepare = prepare
                this.setState(state)
            })
            state[ROW] = this.state[ROW]
            state[ROW]._prepare = prepare
            this.setState(state)
        }
    }
    render() {
        return <div>{this.attached()}</div>
    }
}
var element = document.getElementById('importForm')
if(null != element) {
    ReactDOM.render(<ImportForm />, document.getElementById('importForm'))
}
