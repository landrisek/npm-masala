import React from 'react'

export class Text extends React.Component {
    onChangeText(props, event) {
        this.setState(this.OnChangeText(props, event.target.value))
    }
    OnChangeText(props, state) {
        return {[props.id]:state}
    }
    Text(props, state) {
        return <div className={'form-group'}>
            <label htmlFor={props.label}>{props.label}</label>
            <input className={'form-control'}
                   onChange={this.onChangeText.bind(this, props)}
                   value={state}
                   type={'text'} />
        </div>
    }
}