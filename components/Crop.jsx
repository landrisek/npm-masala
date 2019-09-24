import Cropper from 'react-cropper'
import 'cropperjs/dist/cropper.css'
import React from 'react'

export class Crop extends React.Component {
    constructor(props) {
        super(props)
    }

    Crop(props, state) {
        return <div className={'thumbnail'} key={'gallery-' + props.id}>
            <Cropper alt={'alt'} crop={this.onCropImage.bind(this, props)} guides={false} ref={'cropper'} src={state}/>
        </div>
    }

    onCropImage(props, event) {
        this.state.Crops[props.id] = {
            x: event.detail.x,
            y: event.detail.y,
            width: event.detail.width,
            height: event.detail.height
        };
        this.setState({Crops: this.state.Crops})
    }
}
