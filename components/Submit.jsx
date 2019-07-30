import React from 'react'

export class Submit extends React.Component {
    onClickSubmit(props) {
        this.state.Clicked[props.id] = true
        this.setState({Clicked:this.state.Clicked})
        let state = this.state
        let wysiwyg = this.state.Wysiwyg
        fetch(props.link,
            {body: JSON.stringify(state), headers: {Accept: 'application/json','Content-Type': 'application/json'}, method: 'POST'}).then(
            response => response.json()).then(state => { delete state.Clicked[props.id]
            state.Wysiwyg = wysiwyg
            this.setState(state)
            this.page(props.id)
            this.buildUrl()
            this.OnClickSubmit(props) })
    }
    OnClickSubmit(props) { }
    Submit(props) {
        if(this.isValid(props.id)) {
            let submit = <input className={'btn btn-success'}
                          onClick={this.onClickSubmit.bind(this, props)}
                          style={{marginTop:'10px'}}
                          value={props.label}
                          type={'button'} />
            return this.IsClicked(props, submit)
        }
    }
}


