import React from 'react'

export class Autocomplete extends React.Component {
    constructor(props) {
        super(props)
    }

    Autocomplete(props, state) {
        let current = '';
        let length = 0;
        let list = [];
        for (let key in autocomplete.data) {
            if (length == autocomplete.position) {
                list.push(<li key={current = key}>{Parser(autocomplete.data[key])}</li>)
            } else {
                list.push(<li key={key} style={{display: 'block'}}>{Parser(autocomplete.data[key])}</li>)
            }
            length++
        }
        return <div className={'form-group'}>
            <label htmlFor={props.label}>{props.label}</label>
            <input className={'form-control'}
                   current={current}
                   data-props={JSON.stringify(props)}
                   length={length}
                   onBlur={this.onBlurAutocomplete.bind(this)}
                   onChange={this.onChangeAutocomplete.bind(this, props, state)}
                   onKeyDown={this.onKeyAutocomplete.bind(this, props)}
                   value={state}
                   type={'text'}/>{list}
        </div>
    }

    onBlurAutocomplete() {
        this.setState({Autocomplete: {list: {}, position: 0}})
    }

    onChangeAutocomplete(props, state) {
        fetch(this.props.data[props.id].link,
            {
                body: event.target.value,
                headers: {Accept: 'application/json', 'Content-Type': 'application/json'},
                method: 'POST'
            }).then(
            response => response.json()).then(state => {
            this.setState(state)
        })
    }

    onKeyAutocomplete(props, event) {
        let state = this.state.Autocomplete;
        if (state.position == (parseInt(event.target.getAttribute('length')) - 1)) {
            state.position = 0
        } else if (13 == event.keyCode) {
            this.setState({[props.id]: state.data[event.target.getAttribute('current')].replace(/<\/?[^>]+(>|$)/g, '')});
            state.data = {};
            state.position = 0
        } else if (38 == event.keyCode && 0 == state[props.id].position) {
            state.position = state.data.length
        } else if (38 == event.keyCode) {
            state.position--
        } else if (40 == event.keyCode) {
            state.position++
        }
        this.setState({Autocomplete: state})
    }
}
