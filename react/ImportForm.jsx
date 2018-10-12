import axios from 'axios'
import React, {Component} from 'react'
import ReactDOM from 'react-dom'
import Form from './Form.jsx'

var ID = 'importForm'
var ROW = 'row'
var LINKS = {}

export default class ImportForm extends Form {
    constructor(props){
        super(props, ID)
        LINKS = JSON.parse(ID).getAttribute('data-links')
    }
    done(payload) {
        super.done(payload)
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
            var state = []
            state[ROW] = this.state[ROW]
            for(var file in state[ROW]._import.Attributes.value) { break; }
            state[ROW]._name = file
            state[ROW]._submit.Attributes.style = {display:'none'}
            state[ROW]._import.Attributes.style = {display:'none'}
            this.setState(state)
            state[ROW]._prepare.Attributes.style = {display:'block'}
            axios.post(LINKS.import, state[ROW]).then(response => {
                state[ROW]._prepare.Attributes.data = response.data
                state[ROW]._prepare.Attributes.className = 'btn btn-success'
                delete state[ROW]._name
                this.setState(state)
                this.prepare({target:{id:'_prepare'}})
            })
            this.setState(state)
        }
    }
    render() {
        return <div>{this.attached()}</div>
    }
}
var element = document.getElementById(ID)
if(null != element) {
    ReactDOM.render(<ImportForm />, document.getElementById(ID))
}
