import React, {Component} from 'react';
import ReactDOM from 'react-dom';
import Form from './Form.jsx';

export default class SurveyForm extends Form {
    constructor(props){
        super(props);
        this.state.submited = { display : 'block' };
    }
    click(event) {
        var content = this.state.content;
        if('questions5' == event.target.value) {
            content.Attributes.style = { display: 'block' };
        } else {
            content.Attributes.style = { display: 'none' };
        }
        var answer = this.state.answer;
        answer.value = event.target.value;
        this.setState({ answer: answer, content: content });
        this.succeeded();
    }
    change(event) {
        var element = this.state[event.target.id];
        element.Attributes.value = event.target.value;
        element.Attributes.data.delay = element.Attributes.data.delay + 1;
        var state = [];
        state[event.target.id] = element;
        this.setState(state);
        if(element.Attributes.data.delay < 5 || element.Attributes.data.delay % 10 === 0) {
            this.succeeded();
        }
    }
    submit(event) {
        event.preventDefault();
        var message = this.state['message'];
        message.Attributes.style = { display : 'block' };
        this.setState({ submited: { display: 'none' }, message: message });
    }
    render() {
        return <div>
                <form onSubmit={this.submit} style={this.state.submited}>
                {this.attached()}
                </form>
               </div>
    }
}
ReactDOM.render(<SurveyForm />, document.getElementById('surveyForm'));