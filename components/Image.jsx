import {Draggable, Droppable} from 'react-drag-and-drop'
import React from 'react'

export class Image extends React.Component {
    Image(props, state) {
        return <div className={'thumbnail'} key={'image-' + props.id} style={{float: 'left'}}>
            <Draggable data={props.id} type={'image'}>
                <Droppable accept='image/*' onDrop={this.onDropImage.bind(this, props)} types={['image']}>
                    <img alt={props.id}
                         className={'card-img-top'}
                         height={props.height + ' px'}
                         id={props.id}
                         src={state + '?' + new Date().getTime()}
                         width={props.width + ' px'}/>
                </Droppable>
            </Draggable>
            <div className={'card-body'}>
                <a className={'label label-danger'} id={props.id} onClick={this.onClickImage.bind(this, props)}>
                    <span className={'glyphicon glyphicon-remove'}></span>&nbsp;&nbsp;{props.label}
                </a>
            </div>
        </div>
    }

    onClickImage(props) {
        fetch(props.remove,
            {
                body: JSON.stringify({props: props, state: this.state}),
                headers: {Accept: 'application/json', 'Content-Type': 'application/json'},
                method: 'POST'
            }).then(
            response => response.json()).then(state => {
            this.setState(state);
        })
    }

    onDropImage(props, state) {
        this.state.Image = state.image;
        fetch(props.drop,
            {
                body: JSON.stringify({props: props, state: this.state}),
                headers: {Accept: 'application/json', 'Content-Type': 'application/json'},
                method: 'POST'
            }).then(
            response => response.json()).then(state => {
            this.setState(state)
        })
    }
}

