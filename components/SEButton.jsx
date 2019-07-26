import React from 'react'

export class SEButton extends React.Component {
    constructor(props) {
        super(props)
    }
    SEButton(props) {
        if(this.isValid(props.id)) {
            let button = <a className={props.className ? props.className :  'btn btn-success'} onClick={this.onClickSEButton.bind(this, props)} style={{marginTop:'10px'}}>{props.label}</a>
            return this.IsClicked(props, button)
        }
    }
    onClickSEButton(props) {
        this.state.Clicked[props.id] = 1
        this.setState({Clicked:this.state.Clicked})
        this.Click(props)
        let client = new EventSource(this.props.link + '/' + props.id + '?masala=' + JSON.stringify(this.state))
        let self = this
        client.onmessage = function (message) {
            let clicked = self.state.Clicked
            delete clicked[props.id]
            let state = JSON.parse(message.data)
            self.setState({[message.lastEventId]:state[message.lastEventId],Clicked:clicked})
            self.OnClickSEButton(this, message.lastEventId)
        }
    }
    OnClickSEButton(props, state) {
        props.close()
    }
}