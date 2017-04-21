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
            var closure = this[this.state[key].method];
            if('function' == typeof(closure) && 'addMessage' != this.state[key].method) {
                body.push(this[this.state[key].method](key));
            }
        }
        return body;
    }
    validate() {
        var validated = true;
        for (var key in this.state) {
            for(var validator in this.state[key].validators) {
                var closure = this['is' + validator[0].toUpperCase() + validator.substring(1)];
                if(undefined == this.state[key].attributes.value) {
                    var validate = this.state[key].value;
                } else {
                    var validate = this.state[key].attributes.value;
                }
                if('function' == typeof(closure) && false == closure(validate)) {
                    var state = [];
                    var element = this.state[key];
                    element.validators[validator].style = { display : 'block' }; 
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
                    style={this.state[key].attributes.style}>
                    {this.state[key].attributes.value}</div>
    }
    
    addRadioList(key) {
        var container = [];
        var options = this.state[key].attributes.data;
        container.push(<div>{this.addValidator(key)}</div>);
        for (var value in options) {
            container.push(<div key={value}><input name={key} 
                                    onClick={this.bind(this.state[key].attributes.onClick)}
                                    type='radio'
                                    value={value} />
                                    <label>{this.state[key].attributes.data[value]}</label></div>);
        }
        return container;
    }
    addSubmit(key) {
        return <input key={key}
            onClick={this.submit.bind(this)}
            type='submit' />
    }
    addValidator(key) {
        var container = [];
        var validators = this.state[key].validators;
        for (var validator in validators) {
            var id = key + '_' + validator;
            container.push(<div key={id} className="flash info"
                    style={this.state[key].validators[validator].style}>
                    {this.state[key].validators[validator].value}</div>)
        }
        return container;
    }
    addText(key) {
        return <div key={key} className='input-group'><input 
            id={key}
            className='form-control' 
            data={this.state[key].attributes.data}
            onBlur={this.bind(this.state[key].attributes.onBlur)}
            onClick={this.bind(this.state[key].attributes.onClick)} 
            onChange={this.bind(this.state[key].attributes.onChange)}
            style={this.state[key].attributes.style}
            type='text'
            value={this.state[key].attributes.value} />
            <div>{this.addValidator(key)}</div></div>
    }
    addTitle(key) {
        return <h1 key={key} className={this.state[key].attributes.class}>{this.state[key].attributes.value}</h1>
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
        console.log(value);
        return (undefined != value && '' != value);
    }
    succeeded() {
        this.setState({ masalaSignal : true });
        request('POST', window.location.href, { json: this.state });
    }
}