import React from 'react'

export class Download extends React.Component {
    Download(props, state) {
        if (state) {
            return <a className={'list-group-item list-group-item-success'} download href={state}
                      style={{marginTop: '10px'}}>{props.label}
                <span className={'glyphicon glyphicon-remove'} style={{float: 'right'}}></span>
            </a>
        }
    }

}
