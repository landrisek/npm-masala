import React from 'react'

export class TextArea extends React.Component {
    TextArea(props, state) {
        return <div className={'form-group'}>
            <label htmlFor={props.label}>{props.label}</label>
            <textarea className={'form-control'}
                      onChange={this.onChangeText.bind(this, props)}
                      value={state}
                      type={'textarea'} >{state}</textarea>
        </div>
    }
    onChangeText(props, event) {
        this.setState(this.OnChangeText(props, event.target.value))
    }
    OnChangeText(props, state) {
        return {[props.id]:state}
    }
}
