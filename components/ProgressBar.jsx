import React from 'react'

export class ProgressBar extends React.Component {
    ProgressBar(props, state) {
        return <div className={'progress'} key={props.id + '-progress'} style={{marginTop: '10px'}}>
            <div className={'progress-bar'} style={state ? {width: state + '%'} : {}}></div>
        </div>
    }
}

