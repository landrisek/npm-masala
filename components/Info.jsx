import React from 'react'

export class Info extends React.Component {
    Info(props, state) {
        if(state) {
            return <div className={'alert alert-warning alert-dismissible show'} role={'alert'}
                        onClick={this.onClickInfo.bind(this, props)}>
                <strong>{props.label}</strong>
                <button aria-label={'Close'} className={'close'} type={'button'}>
                    <span aria-hidden={'true'}>&times;</span>
                </button>
            </div>
        }
    }
    onClickInfo(props) {
        this.setState({[props.id]:false})
    }
}
