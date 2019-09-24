import React from 'react'

export class Button extends React.Component {
    constructor(props) {
        super(props)
    }
    Button(props) {
        if (this.isValid(props.id)) {
            return <a className={props.className ? props.className : 'btn btn-success'}
                      onClick={this.onClickButton.bind(this, props)} style={{marginTop: '10px'}}>{props.label}</a>
        }
    }
    onClickButton(props) {
        let self = this;
        this.setState({Clicked: {[props.id]: true}});
        fetch(props.link,
            {
                body: JSON.stringify(this.state),
                headers: {Accept: 'application/json', 'Content-Type': 'application/json'},
                method: 'POST'
            }).then(
            response => response.json()).then(state => {
            self.OnClickButton(props, state)
        })
    }

    OnClickButton(props, state) {
        if (parseInt(state.Paginator.Last) >= parseInt(state.Paginator.Current)) {
            state[props.id] = state.Paginator.Current / (state.Paginator.Last / 100);
            this.setState(state);
            this.buildUrl();
            this.onClickButton(props)
        } else {
            state.Paginator.Current = 1;
            state[props.id] = 0;
            delete state.Clicked[props.id];
            this.setState(state);
            this.buildUrl()
        }
    }

}
