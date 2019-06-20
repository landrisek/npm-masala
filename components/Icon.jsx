import React from 'react'

export class Icon {
    Icon(props){
        let icon = <button onClick={this.onClickIcon.bind(this, props)} type={'button'}>
            <span aria-hidden={'true'} className={props.className ? props.className : 'glyphicon glyphicon-edit'}></span>
        </button>
        return this.IsClicked(props.id, icon)
    }
    onClickIcon(props) {
        this.state.Clicked[props.id] = true
        this.setState({Clicked:this.state.Clicked})
        fetch(props.link,
            {body: JSON.stringify(this.state), headers: {Accept: 'application/json','Content-Type': 'application/json'}, method: 'POST'}).then(
            response => response.json()).then(state => { delete state.Clicked[props.id]
            this.setState(state) })
    }
}