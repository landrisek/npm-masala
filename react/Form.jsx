import React, {Component} from 'react';
import request from 'sync-request';

function getMethods(object) {
    var results = [];
    for(var method in object) {
        if(typeof object[method] === "function") {
            results.push(method);
        }
    }
    return results;
}

export default class Form extends Component {
    constructor(props){
        super(props);
        var name = this.constructor.name;
        this.state = JSON.parse(document.getElementById(name[0].toLowerCase() + name.substring(1, name.length)).getAttribute('data'));
    }
    attached() {
        var body = [];
        for (var key in this.state) {
            var closure = this[this.state[key].Method];
            if('function' == typeof(closure) && 'addMessage' != this.state[key].Method) {
                body.push(this[this.state[key].Method](key));
            }
        }
        return body;
    }
    validate() {
        var validated = true;
        for (var key in this.state) {
            for(var validator in this.state[key].Validators) {
                var closure = this['is' + validator[0].toUpperCase() + validator.substring(1)];
                if(undefined == this.state[key].Attributes.value) {
                    var validate = this.state[key].value;
                } else {
                    var validate = this.state[key].Attributes.value;
                }
                if('function' == typeof(closure) && false == closure(validate)) {
                    var state = [];
                    var element = this.state[key];
                    element.Validators[validator].style = { display : 'block' }; 
                    state[key] = element;
                    this.setState(state);
                    validated = false;
                }
            }
        }
        return validated;
    }
    addMessage(key) {
        return <div key={key} 
                    className="flash info"
                    style={this.state[key].Attributes.style}>
                    {this.state[key].Attributes.value}</div>
    }
    
    addRadioList(key) {
        var container = [];
        var options = this.state[key].Attributes.data;
        container.push(<div>{this.addValidator(key)}</div>);
        for (var value in options) {
            container.push(<div key={value}><input name={key} 
                                    onClick={this.bind(this.state[key].Attributes.onClick)}
                                    type='radio'
                                    value={value} />
                                    <label>{this.state[key].Attributes.data[value]}</label></div>);
        }
        return container;
    }
    addSubmit(key) {
        return <input key={key}
            className={this.state[key].Attributes.class}
            onClick={this.submit.bind(this)}
            value={this.state[key].Attributes.value}
            type='submit' />
    }
    addValidator(key) {
        var container = [];
        var validators = this.state[key].Validators;
        for (var validator in validators) {
            var id = key + '_' + validator;
            container.push(<div key={id} className='flash info'
                    style={this.state[key].Validators[validator].style}>
                    {this.state[key].Validators[validator].value}</div>)
        }
        return container;
    }
    addText(key) {
        return <div key={key} className='form-group'><input
            id={key}
            className='form-control'
            data={this.state[key].Attributes.data}
            onBlur={this.bind(this.state[key].Attributes.onBlur)}
            onClick={this.bind(this.state[key].Attributes.onClick)} 
            onChange={this.bind(this.state[key].Attributes.onChange)}
            placeholder={this.state[key].Attributes.placeholder}
            style={this.state[key].Attributes.style}
            type='text'
            value={this.state[key].Attributes.value} />
            <div>{this.addValidator(key)}</div>
            <div dangerouslySetInnerHTML={{__html: this.state[key].appendix}} />
            </div>
    }
    addTitle(key) {
        return <h1 key={key} className={this.state[key].Attributes.class}>{this.state[key].Attributes.value}</h1>
    }
    bind(method) {
        if(undefined === method) {
            return;
        }
        var closure = method.replace(/\(/, '').replace(/\)/, '');
        if('function' == typeof(this[closure])) {
            return this[closure].bind(this);
        }
        return;
    }
    isRequired(value) {
        return (undefined != value && '' != value);
    }
    succeeded() {
        this.setState({ masalaSignal : true });
        request('POST', window.location.href, { json: this.state });
    }
}