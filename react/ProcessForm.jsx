import React, {Component} from 'react'
import ReactDOM from 'react-dom'
import Form from './Form.jsx'

export default class ProcessForm extends Form {
    constructor(props){
        super(props)
    }
    done(payload) {
        super.done(payload)
        var element = this.state['done']
        element.Attributes.style = {display:'block'}
        this.setState({'done':element})
    }
    onChange(event) {
        var element = this.state[event.target.id]
        if('checkbox' == event.target.type && 'checked' == element.Attributes.checked) {
            element.Attributes.checked = null
        } else if('checkbox' == event.target.type) {
            element.Attributes.checked = 'checked'
        }
        var state = []
        state[event.target.id] = element
        this.setState(state)
    }
    render() {
        return <div>{this.attached()}</div>
    }
}
ReactDOM.render(<ProcessForm />, document.getElementById('processForm'))