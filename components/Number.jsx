import React from 'react'

export class Number extends React.Component {
    Number(props, state) {
        return <input className={'form-control'}
                      onChange={this.onChangeNumber.bind(this, props)}
                      style={props.style ? props.style : {}}
                      value={state}
                      title={props.title ? props.title : ''}
                      type={'number'} />
    }
    onChangeNumber(props, event) {
        this.setState(this.OnChangeNumber(props, event.target.value))
    }
    OnChangeNumber(props, state) {
        return {[props.id]:state}
    }
}

